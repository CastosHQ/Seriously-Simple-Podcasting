import { dateI18n } from '@wordpress/date';
import { useState } from '@wordpress/element';
import { DateTimePicker, Popover, TextControl } from '@wordpress/components';
import moment from 'moment';

const DateInput = ({value, onChange}) => {
	const displayedDateFormat = (val) => {
		return val ? dateI18n('j F, Y', moment.utc(val)) : '';
	}

	const dateFormat = (val) => {
		return val ? moment.utc(val).format('YYYY-MM-DD') : '';
	}

	const [isCalendarOpen, setIsCalendarOpen] = useState(false);

	const toggleCalendar = () => {
		setIsCalendarOpen(!isCalendarOpen);
	};

	return (
		<div>
			<TextControl
				className={'ssp-calendar-icon'}
				value={ displayedDateFormat(value) }
				onChange={() => {}}
				onClick={toggleCalendar}
			/>

			{/* Show the calendar pop-up when input is clicked */}
			{isCalendarOpen && (
				<Popover className={'ssp-date-input'}
						 position="bottom center"
						 onClose={() => setTimeout(() => {
							 setIsCalendarOpen(false)
						 }, 100)}>
					<DateTimePicker
						currentDate={value}
						onChange={(date) => {
							onChange(
								dateFormat(date),
								displayedDateFormat(date)
							);
						}}
						startOfWeek={1}
					/>
				</Popover>
			)}
		</div>
	);
};

export default DateInput;
